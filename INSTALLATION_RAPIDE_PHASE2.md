# 📦 Installation Rapide Phase 2 - NOIA v4.0

## ⚠️ PROBLÈME ACTUEL

Vous recevez ce message :
```
⚠️ Mode démonstration — Réponse simulée (serveur PHP requis pour l'API réelle)
```

**Cause :** Les fichiers Phase 2 ne sont **pas uploadés** sur votre serveur erelys.fr.

---

## ✅ SOLUTION : Upload des 3 Fichiers

### **Fichiers à télécharger depuis le dépôt Git**

Vous devez uploader ces 3 fichiers sur votre serveur :

```
1. api/pre_analysis.php              (NOUVEAU - pré-analyse intelligente)
2. api/legal_facts_manager.php       (NOUVEAU - gestionnaire legal facts)
3. api/proxy.php                     (MODIFIÉ - version 4.0.0 améliorée)
```

---

## 📤 Procédure d'Upload (FileZilla ou FTP)

### **Étape 1 : Télécharger les fichiers depuis Git**

Les fichiers sont dans votre dépôt GitHub. Vous pouvez :

**Option A : Télécharger via l'interface Git**
- Aller sur votre dépôt
- Branch : `claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek`
- Télécharger les 3 fichiers

**Option B : Cloner le dépôt en local**
```bash
git clone [URL_DE_VOTRE_DEPOT]
git checkout claude/remove-make-interface-011CUTxrLten4c6qKf8YMNek
```

Puis récupérer les fichiers dans :
```
NOIA_V1/api/pre_analysis.php
NOIA_V1/api/legal_facts_manager.php
NOIA_V1/api/proxy.php
```

---

### **Étape 2 : Connexion FTP**

**Ouvrir FileZilla** (ou votre client FTP) :

```
Hôte : ftp.erelys.fr (ou votre hôte OVH)
Utilisateur : [votre_login_FTP]
Mot de passe : [votre_mdp_FTP]
Port : 21
```

---

### **Étape 3 : Backup de l'ancien proxy.php (sécurité)**

Avant de remplacer, **faites un backup** :

1. Naviguer vers `/www/noia/api/`
2. Clic droit sur `proxy.php` → **Renommer** → `proxy_OLD_backup.php`
3. Garder ce fichier en cas de problème

---

### **Étape 4 : Upload des nouveaux fichiers**

**Via FileZilla :**

1. **Côté local** (gauche) : Naviguer vers vos fichiers téléchargés

2. **Côté serveur** (droite) : Naviguer vers `/www/noia/api/`

3. **Glisser-déposer** les 3 fichiers :
   ```
   pre_analysis.php              → /www/noia/api/
   legal_facts_manager.php       → /www/noia/api/
   proxy.php                     → /www/noia/api/ (écrase l'ancien)
   ```

4. **Vérifier les permissions** (clic droit → Permissions) :
   ```
   pre_analysis.php              : 644 (rw-r--r--)
   legal_facts_manager.php       : 644 (rw-r--r--)
   proxy.php                     : 644 (rw-r--r--)
   ```

---

## ✅ Vérification Post-Upload

### **Test 1 : Vérifier que les fichiers existent**

Via FTP, vérifier que vous voyez bien :
```
/www/noia/api/pre_analysis.php              ✅
/www/noia/api/legal_facts_manager.php       ✅
/www/noia/api/proxy.php                     ✅ (version 4.0.0)
```

---

### **Test 2 : Tester l'API directement**

**Option A : Via navigateur**

Accéder directement à :
```
https://noia.erelys.fr/api/proxy.php
```

**Résultat attendu :** Erreur 405 "Méthode non autorisée. Utilisez POST."

**✅ Si vous voyez cette erreur = L'API fonctionne !**

**❌ Si vous voyez une erreur PHP = Problème d'installation**

---

### **Test 3 : Tester via l'interface NOIA**

1. **Aller sur :** `https://noia.erelys.fr/`

2. **Se connecter** (si pas déjà fait)

3. **Poser cette question :**
   ```
   Quelle est la grille indiciaire pour un adjoint administratif territorial ?
   ```

4. **Résultat attendu :**
   - ❌ Plus de message "Mode démonstration"
   - ✅ Réponse détaillée avec :
     - 1️⃣ Références juridiques
     - 2️⃣ Analyse de la situation
     - 3️⃣ Application pratique (grille complète avec TOUS les échelons)
     - 4️⃣ Proposition d'usage
   - ✅ Décret n° 85-1148 cité
   - ✅ Valeur du point : 4,92302 €
   - ✅ Tableau complet avec indices bruts + majorés + salaires

---

## 🔍 Dépannage

### **Problème 1 : Toujours "Mode démonstration"**

**Cause :** L'API ne répond pas (erreur PHP)

**Solution :**

1. **Activer DEBUG_MODE** dans `config/config.php` :
   ```php
   define('DEBUG_MODE', true);
   ```

2. **Recharger NOIA** et ouvrir la Console (F12)

3. **Regarder les erreurs** dans l'onglet Console ou Network

4. **Erreurs possibles :**
   - `Class 'PreAnalysis' not found` → Fichier `pre_analysis.php` non uploadé
   - `Class 'LegalFactsManager' not found` → Fichier `legal_facts_manager.php` non uploadé
   - `Table 'legal_facts' doesn't exist` → Base de données Phase 1 incomplète

---

### **Problème 2 : Erreur "Table 'legal_facts' doesn't exist"**

**Cause :** Table `legal_facts` non créée (Phase 1 incomplète)

**Solution :**

Exécuter dans phpMyAdmin :
```sql
-- 1. Vérifier si la table existe
SHOW TABLES LIKE 'legal_facts';

-- 2. Si absente, exécuter les scripts :
-- Fichier : database/upgrade_v4_PARTIE_A.sql
-- Puis : database/legal_facts_data.sql
```

---

### **Problème 3 : Erreur PHP "Parse error" ou "Syntax error"**

**Cause :** Fichiers corrompus pendant l'upload

**Solution :**

1. **Re-télécharger** les fichiers depuis Git (ne pas copier/coller le code)
2. **Upload en mode BINAIRE** (FileZilla : Transfert → Type de transfert → Binaire)
3. **Vérifier l'encodage** : UTF-8 sans BOM

---

### **Problème 4 : Réponses toujours courtes/peu détaillées**

**Cause :** Ancien proxy.php encore en cache

**Solution :**

1. **Vider le cache du navigateur** (Ctrl+Shift+Del)
2. **Recharger en navigation privée**
3. **Vérifier que proxy.php est bien la v4.0.0** :
   ```php
   // Ligne 4 de proxy.php doit afficher :
   * Version: 4.0.0 - Phase 2 : Intelligence & Précision Légale
   ```

---

## 🎯 Checklist Finale

```
Upload :
☐ api/pre_analysis.php uploadé (permissions 644)
☐ api/legal_facts_manager.php uploadé (permissions 644)
☐ api/proxy.php v4.0.0 uploadé (permissions 644)
☐ Backup de l'ancien proxy.php effectué

Vérifications base de données :
☐ Table legal_facts existe
☐ Table legal_facts contient 20+ règles actives
☐ Compte admin existe dans table users

Tests fonctionnels :
☐ https://noia.erelys.fr/api/proxy.php retourne erreur 405 (OK)
☐ Plus de message "Mode démonstration"
☐ Question RH retourne grille complète avec tous les échelons
☐ Réponse structurée avec 1️⃣ 2️⃣ 3️⃣ 4️⃣
☐ Décrets et sources cités exactement
```

---

## 📞 Si Rien ne Fonctionne

**Derniers recours :**

1. **Télécharger les logs d'erreur PHP** :
   - Via FTP : `/www/noia/logs/`
   - Ou via cPanel → Logs → Error Log

2. **Vérifier la version PHP du serveur** :
   - Minimum requis : PHP 7.4+
   - Recommandé : PHP 8.1+

3. **Tester manuellement l'inclusion des fichiers** :

Créer un fichier `test-phase2.php` dans `/www/noia/` :
```php
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/pre_analysis.php';
require_once __DIR__ . '/api/legal_facts_manager.php';

echo "✅ pre_analysis.php : OK\n";
echo "✅ legal_facts_manager.php : OK\n";

$analysis = PreAnalysis::analyze("Quel est le quorum ?");
echo "✅ PreAnalysis fonctionne\n";
print_r($analysis);

$manager = new LegalFactsManager();
echo "✅ LegalFactsManager fonctionne\n";
?>
```

Accéder à : `https://noia.erelys.fr/test-phase2.php`

**Résultat attendu :** Affichage de "✅ OK" pour chaque fichier

**⚠️ Supprimer test-phase2.php après le test !**

---

## ✅ Succès !

Une fois que tout fonctionne :

1. **Désactiver DEBUG_MODE** dans `config/config.php` :
   ```php
   define('DEBUG_MODE', false);
   ```

2. **Tester avec plusieurs questions** :
   - Quorum : "Quel est le quorum pour une reconvocation ?"
   - FCTVA : "Quelles dépenses sont éligibles au FCTVA ?"
   - M57 : "Quel compte M57 pour un broyeur ?"
   - RH : "Quelle est la grille indiciaire pour un adjoint administratif territorial ?"

3. **Vérifier la qualité des réponses** :
   - ✅ Structure avec 1️⃣ 2️⃣ 3️⃣ 4️⃣
   - ✅ Décrets cités exactement
   - ✅ Grilles RH complètes avec tous les échelons
   - ✅ Sources mentionnées (emploi-collectivites.fr, Légifrance, etc.)

---

**🎉 Phase 2 opérationnelle !**

Les réponses de NOIA seront maintenant **complètes, précises et détaillées** comme vous le souhaitez.
